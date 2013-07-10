//
//  mover.cpp
//  natureofcode_1_ex_1_7
//
//  Created by Tobias Treppmann on 5/31/13.
//
//

#include "mover.h"

Mover::Mover(){
    location.set(ofRandom(ofGetWidth()), ofRandom(ofGetHeight()));
    velocity.set(0,0);
    acceleration.set(0, 0);
    topspeed = 10;
}

void Mover::update() {
    ofVec2f mouse;
    mouse.set(ofGetMouseX(), ofGetMouseY());
    ofVec2f dir;
    dir.set(mouse.operator-(location));
    float magnitude = dir.length();
    magnitude = ofMap(magnitude, ofGetWidth(), 0, 0, .5);
    dir.normalize();
    dir.operator*=(magnitude);

    acceleration.set(dir);
    velocity.operator+=(acceleration);
    velocity.limit(topspeed);
    location.operator+=(velocity);
}

void Mover::display() {
    ofColor color(50);
    ofEllipse(location.x, location.y, 16, 16);
}

void Mover::checkEdges() {
    if (location.x > ofGetWidth()) {
        location.x = 0;
    } else if (location.x < 0) {
        location.x = ofGetWidth();
    }
    
    if (location.y > ofGetHeight()) {
        location.y = 0;
    } else if (location.y < 0) {
        location.y = ofGetHeight();
    }
}