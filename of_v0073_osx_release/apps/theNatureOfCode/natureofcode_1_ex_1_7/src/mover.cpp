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
    acceleration.set(-0.001, 0.01);
    topspeed = 10;
}

void Mover::update() {
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