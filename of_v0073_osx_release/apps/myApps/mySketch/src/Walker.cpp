//
//  Walker.cpp
//  emptyExample
//
//  Created by Tobias Treppmann on 5/17/13.
//
//

#include "Walker.h"

//--------------------------------------------------------------
void Walker::init(){
    tx = 1;
    ty = 10000;
    x = ofGetWidth()/2;
    y = ofGetHeight()/2;
}

//--------------------------------------------------------------
void Walker::step() {
    // x- and y-location mapped from noise
    
    stepsizex = ofMap(ofNoise(tx),0,1,-20,20);
    stepsizey = ofMap(ofNoise(ty),0,1,-20,20);
    
    x += stepsizex;
    y += stepsizey;
    
    if(x >= ofGetWidth()-10) {
        x = ofGetWidth()-10;
    } else if (x <= 10) {
        x = 10;
    }
    
    if(y >= ofGetHeight()-10) {
        y = ofGetHeight()-10;
    } else if (y <= 10) {
        y = 10;
    }
    
    ofSetColor(0,0,0);
    ofFill();
    ofEllipse(x, y, 10, 10);
    
    // Move forward through “time.”
    tx += 0.01;
    ty += 0.01;
}
