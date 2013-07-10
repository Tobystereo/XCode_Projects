//
//  balloon.cpp
//  2_1
//
//  Created by Tobias Treppmann on 6/11/13.
//
//

#include "balloon.h"

Balloon::Balloon() {
    location.set(ofGetWidth()/2, ofGetHeight()/2);
    velocity.set(0.0,0.0);
    acceleration.set(0.0,0.0);
    noiseX = 1.0;
    noiseY = 10000.0;
}

void Balloon::update() {
    target.set(ofGetWidth()/2, 0.0);
    dir.set(target.operator-(location));
    float anything = 0.5;
    dir.normalize();
    dir.operator*=(anything);
    
    acceleration = dir;
    
    wind.set(ofNoise(noiseX), ofNoise(noiseY));
    wind.operator*=(anything);
    acceleration.operator+=(wind);
    
    velocity.operator+=(acceleration);
    velocity.limit(topspeed);
    location.operator+=(velocity);
    
    wind.operator*=(0);
    noiseX += 0.01;
    noiseY += 0.01;
}


void Balloon::display() {
    ofSetColor(175, 175, 175);
    ofCircle(location.x, location.y, 25);
}

void Balloon::checkEdges() {
    if (location.x > ofGetWidth()) {
        location.x = 0;
    } else if (location.x < 0) {
        location.x = ofGetWidth();
    }
    
    if (location.y > ofGetHeight()) {
        location.y = 0;
    } else if (location.y < 0) {
        location.y = 0;
    }
}
